"use client"
import React, { useState } from 'react'
import Security from './Settings/Security';
import Delegation from './Settings/Delegation';
import Users from './Settings/Users';
import Access from './Settings/Access';

const UserSettings = () => {
  const [active, setActive] = useState("security");

  const tabClasses = (tab) =>
    `relative px-13 py-3 font-semibold transition-colors duration-300 hover:text-black cursor-pointer
     ${active === tab ? "text-[#7BC9EE]" : "text-gray-600"}
     after:content-[''] after:absolute after:left-0 after:bottom-0 
     after:h-[2px] after:bg-[#7BC9EE] after:transition-all after:duration-300 
     ${active === tab ? "after:w-full" : "after:w-full after:bg-gray-200 "}`;

  const renderContent = () => {
    switch (active) {
    
      case "delegation":
        return <div><Delegation/></div>;

      case "users":
        return <div><Users/></div>;

      case "access":
        return <div><Access/></div>;

      default:
        return <div><Security/></div>;
    }
  };

  return (
    <div>
      <h1 className='text-3xl font-bold mb-2 text-[#7BC9EE]'>Settings</h1>
      <hr className='mt-3'/>
      
      <div className="flex justify-center my-8">
        <button className={tabClasses("security")} onClick={() => setActive("security")}>
          Security
        </button>
        <button className={tabClasses("delegation")} onClick={() => setActive("delegation")}>
          Delegation
        </button>
        <button className={tabClasses("users")} onClick={() => setActive("users")}>
          Users
        </button>
        <button className={tabClasses("access")} onClick={() => setActive("access")}>
          Access
        </button>
      </div>

      <div>{renderContent()}</div>
    </div>
  );
};

export default UserSettings;
