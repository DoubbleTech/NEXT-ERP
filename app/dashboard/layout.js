import Navbar from "@/components/Dashboard/Navbar";
import React from "react";

const layout = ({ children }) => {
  return (
    <div className="flex flex-col h-screen bg-gray-200">
   <div >
    <Navbar/>
   </div>
   <div>
    {children}
   </div>
    </div>
  );
};

export default layout;
